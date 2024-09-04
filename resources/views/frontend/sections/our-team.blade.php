<section class="py-5 bg-primary">
   <div class="container">
      <div class="row py-5">
         <div class="col-lg-12"> 
            <div class="text-center w-100  mx-auto" >
               <div class=" px-5">
                  <h1 class="text-light poppins-bold">{{ $ourTeamPage->short_title }} <span class="txt-secondary"> {{ $ourTeamPage->long_title }} </span> 
                  At <br> {{ $ourTeamPage->short_description }}</h1>
               </div>
             
                  <p class="text-light w-75 text-center my-3 mx-auto" >
                     {!! $ourTeamPage->long_description !!}
                  </p>
             
               <div>
                   <a href="{{ route('frontend.getQuotation') }}" class="btn btn-theme poppins-medium">Get Your dedicated Partner</a>
               </div>
            </div>

         </div>
      </div>
   </div>
 </section>

 <section class="py-4">
   <div class="container"> 
      <div class="row">
         <div class="col-md-12 pt-5">
            <div class="">
              <h2 class="poppins-bold text-center txt-primary">Meet our Excellent people </h2>
            </div> 
         </div>
      </div>

      <div class="row g-0">

         @foreach($ourTeam as $teamMember)
            <div class="col-lg-4 col-md-6 col-sm-12 mt-5">
                  <div class="card text-center border-0  p-4">
                     <div>
                        <img src="{{ asset($teamMember->image) }}" class="img-fluid" alt="">
                     </div>
                     <div class="mt-3">
                        <h6 class="poppins-bold txt-primary" >{{ $teamMember->title }} {{ $teamMember->name }}</h6>
                        <span class="txt-primary">{{ $teamMember->position }}</span>
                     </div>
                  </div>
            </div>
         @endforeach

      </div>

   </div>
 </section>
